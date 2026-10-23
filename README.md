# Hotel Management System (HMS)

A back-office system for hotel **owners** and **staff**. There is no guest-facing
booking: staff enter every reservation, check-in, order and payment themselves.

Built with **Laravel 12 + MySQL**. The dashboard uses a **liquid glass** theme
(translucent, blurred, refractive surfaces over an animated gradient), written in
plain CSS (`public/css/liquid-glass.css`), so there is no front-end build step.

## Features

| Area | What it does |
|---|---|
| Dashboard | Total / available / occupied rooms, today's check-ins, check-outs, bookings and revenue, pending payments, room board, arrivals and departures due, 7-day revenue chart |
| Rooms | Add / edit / delete rooms (owner), number, type, capacity, price, status: Available, Occupied, Reserved, Cleaning, Maintenance. Room types (Single, Double, Deluxe, Suite or your own) |
| Guests | Name, phone, e-mail, address, ID proof, check-in / check-out, stay history, room-service food orders, amenity issue and return checks (towel, kettle, slippers, plates…) |
| Billing | Invoice per stay: room charges, room service, extras, missing-item charges, tax, discount, advances, outstanding balance, payment status (unpaid / partial / paid). No payment method is stored. Printable |
| Housekeeping | Cleaning status, task assignment, room-readiness update (finishing a task frees the room), maintenance requests that can block a room |
| Reports | Daily and monthly revenue, occupancy, bookings and cancellations, payments and outstanding, check-in / check-out (with the staff who handled each). CSV download |
| Users & settings | Owner and staff logins, role-based permissions, hotel details, invoice prefix / footer, tax, currency, check-in / check-out times |

### Roles

* **Owner** can change anything: rooms and prices, room types, menu, amenities, staff, settings, reports, discounts.
* **Staff** (many per hotel) run daily operations: guests, bookings, check-in / out, orders, payments, housekeeping, maintenance, room status.

### Staff ID on every entry

Each staff member has a **Staff ID** (employee code). It is filled in automatically from the login on every form that creates or
changes something, and the server checks it matches the logged-in account. Records
store who did what (`created_by`, `checked_in_by`, `checked_out_by`, `received_by`,
`issued_by`, `completed_by`…) so you can later see which employee served which
service. Staff accounts are **disabled, never deleted**, so history stays intact.

### Multiple hotels / clients

Every table carries a `hotel_id` and a global Eloquent scope (`BelongsToHotel`)
limits all queries to the logged-in user's hotel and stamps new rows with it.
One installation can serve many hotels, each with its own owner, staff, rooms,
prices, tax settings and invoice numbering.

Onboard a new hotel:

```bash
php artisan hms:create-hotel "Hotel Name" owner@hotel.com --owner-name="Owner Name"
```

## Setup

Requirements: PHP 8.2+, Composer, MySQL 8 (or MariaDB 10.6+).

```bash
composer install
cp .env.example .env
php artisan key:generate

# create an empty MySQL database called `hms`, then set DB_* in .env
php artisan migrate
php artisan db:seed        # optional demo hotel, rooms, menu and amenities
php artisan serve
```

Demo logins (after `db:seed`):

| Role | E-mail | Password | Staff ID |
|---|---|---|---|
| Owner | owner@hms.test | password | OWN001 |
| Staff | staff@hms.test | password | EMP001 |

**Change or delete the demo accounts before going live.**

Tests (use in-memory SQLite): `composer test`

## Daily flow

1. **Stays & bookings → New**: pick or add a guest, choose a room, dates, rate and advance. *Check in now* (needs ID proof) or *Reserve room*.
2. While in house, on the stay page: place room-service orders, issue items (towels, kettle…), add extra charges, record payments.
3. **Check out**: count items returned. Anything missing is billed at its replacement price. The room moves to *Cleaning*, a housekeeping task is created and the invoice is generated.
4. Housekeeping marks the task done and the room becomes *Available*.

## Billing rules

* Nights = calendar days from check-in to check-out (minimum 1). In-house stays are estimated to the expected check-out date, or today if the guest has stayed longer.
* Tax (hotel's tax %) applies to room, room service and extra charges. Missing-item charges are not taxed.
* Balance = total − (advances + payments). Status updates automatically.

## Project layout

```
app/Models/Concerns/BelongsToHotel.php   multi-hotel scope
app/Services/BillingService.php          invoice maths
app/Http/Controllers/                    one controller per module
database/migrations/                     schema
resources/views/                         Blade views (layouts/app = glass shell)
public/css/liquid-glass.css              the theme
tests/Feature/HotelWorkflowTest.php      tenancy, roles, staff-ID and billing tests
```
