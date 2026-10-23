@extends('layouts.app')
@section('title', 'Housekeeping')
@section('heading', 'Housekeeping')

@section('content')
    <div class="grid g-main">
        <section class="glass pad">
            <h2>Open tasks</h2>
            <div class="table-wrap"><table>
                <thead><tr><th>Room</th><th>Task</th><th>Assigned to</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($open as $task)
                    <tr>
                        <td><b>{{ $task->room->room_number }}</b> <span class="muted small">{{ $task->room->type->name }}</span></td>
                        <td>{{ ucfirst(str_replace('_', ' ', $task->type)) }}<div class="muted small">{{ $task->notes }}</div></td>
                        <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                        <td><x-pill :status="$task->status"/></td>
                        <td>
                            <details class="fold"><summary class="btn sm" style="list-style:none">Update</summary>
                                <form method="POST" action="{{ route('housekeeping.update', $task) }}" class="stack" style="min-width:210px">@csrf @method('PATCH')
                                    <select name="status">
                                        <option value="in_progress">In progress</option>
                                        <option value="done">Done · room is clean &amp; ready</option>
                                    </select>
                                    <x-staff-code :uid="'t'.$task->id"/>
                                    <button class="btn sm primary" type="submit">Save</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">All clear. No open cleaning tasks.</td></tr>
                @endforelse
                </tbody>
            </table></div>

            <h2 style="margin-top:26px">Recently completed</h2>
            <div class="table-wrap"><table>
                <tbody>
                @forelse($recent as $task)
                    <tr><td>Room {{ $task->room->room_number }}</td><td>{{ ucfirst(str_replace('_', ' ', $task->type)) }}</td>
                        <td>{{ $task->completedBy?->name }} <span class="muted">({{ $task->completedBy?->employee_code }})</span></td>
                        <td class="muted">{{ $task->completed_at?->format('d M H:i') }}</td></tr>
                @empty
                    <tr><td class="empty">Nothing completed yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="glass pad">
            <h2>Assign a task</h2>
            <form method="POST" action="{{ route('housekeeping.store') }}" class="stack">@csrf
                <div><label>Room</label>
                    <select name="room_id" required>
                        <option value="">Select room</option>
                        @foreach($rooms as $r)<option value="{{ $r->id }}">{{ $r->room_number }} · {{ ucfirst($r->status) }}</option>@endforeach
                    </select>
                </div>
                <div><label>Task</label>
                    <select name="type">
                        @foreach(['cleaning' => 'Cleaning', 'checkout_cleaning' => 'Check-out cleaning', 'turndown' => 'Turndown', 'deep_clean' => 'Deep clean', 'inspection' => 'Inspection'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div><label>Assign to</label>
                    <select name="assigned_to"><option value="">Unassigned</option>@foreach($team as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->employee_code }})</option>@endforeach</select>
                </div>
                <div><label>Notes</label><input name="notes"></div>
                <x-staff-code uid="hk"/>
                <button class="btn primary" type="submit">Assign task</button>
            </form>
            @if($cleaningRooms->isNotEmpty())
                <p class="muted small" style="margin-top:18px">Rooms in cleaning: {{ $cleaningRooms->pluck('room_number')->join(', ') }}</p>
            @endif
        </section>
    </div>
@endsection
