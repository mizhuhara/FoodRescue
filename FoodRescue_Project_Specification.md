# FoodRescue — Master Project Specification

## 1. Project Overview

**FoodRescue** is a mobile-first platform designed to reduce food waste by connecting restaurants, cafes, bakeries, warungs, hotels, catering businesses, and food-based UMKM that have surplus food with customers who want to purchase that food at a discounted rescue price.

**Tagline:**  
> Save Food. Save Money.

FoodRescue is **pickup-first**, not delivery-first. Customers discover surplus food nearby, reserve/order it, and collect it during a specified pickup window.

### Core example

- Food: Chicken Rice
- Normal price: Rp25.000
- Rescue price: Rp12.000
- Stock: 5
- Pickup: 18:00–20:00
- Distance: 1.2 km

---

## 2. Goals

1. Reduce edible food waste.
2. Help food businesses recover revenue from surplus food.
3. Help customers access food at lower prices.
4. Show nearby surplus food using location.
5. Provide a clear order and pickup workflow.
6. Measure positive impact through food-rescued statistics.

FoodRescue must contain real business logic, not just CRUD:

- Stock management
- Order management
- Pickup windows
- Order expiration
- Role-based access
- Location and distance calculation
- QR pickup verification
- Notifications
- Reviews
- Impact statistics

---

# 3. Technology Stack

## Mobile

- Flutter
- Dart
- Riverpod
- Dio
- Flutter Secure Storage
- Geolocator
- Google Maps Flutter or an OpenStreetMap-based solution
- Image Picker
- QR code scanner package
- Firebase Cloud Messaging for push notifications

## Backend

- Laravel 12
- PHP 8.3+
- Laravel Sanctum
- REST API
- JSON
- Form Requests
- API Resources
- Policies / Gates
- Laravel Notifications

## Database

- MySQL 8+

## Storage

- Laravel Storage
- Local filesystem during development
- S3-compatible storage can be added later

## Admin

- Laravel web dashboard

## Development

- Windows
- VS Code
- Android Studio
- Git
- GitHub
- Laragon

## Testing

Backend:
- Pest or PHPUnit
- Feature Tests
- Unit Tests

Mobile:
- Flutter Test
- Widget Tests
- Integration Tests

---

# 4. System Architecture

```text
                    FOODRESCUE
                        |
             +----------+----------+
             |                     |
        Flutter App           Admin Web
             |                  Laravel
             |                     |
             +---------+-----------+
                       |
                  REST API
                       |
                  Laravel 12
                       |
          +------------+------------+
          |            |            |
        MySQL       Storage        FCM
          |            |            |
        Data         Images      Push Notifications
```

Flutter must never access MySQL directly.

All application data must be accessed through the Laravel REST API.

Use API versioning:

```text
/api/v1/...
```

---

# 5. User Roles

## CUSTOMER

Can:

- Register/login
- Discover food
- Search food
- Filter food
- View nearby food
- View food details
- Favorite food/partners
- Create orders
- View order history
- View order status
- View QR pickup code
- Complete pickup
- Review completed orders
- View personal impact statistics

## PARTNER

Represents:

- Restaurant
- Cafe
- Bakery
- Warung
- Hotel
- Catering
- Food UMKM

Can:

- Create business profile
- Submit business for verification
- Add food
- Edit food
- Manage stock
- Set normal price
- Set rescue price
- Set pickup window
- View orders
- Update order status
- Verify customer pickup
- View business impact statistics

## ADMIN

Can:

- Manage users
- Verify partners
- Suspend partners
- Manage categories
- Monitor food
- Monitor orders
- Manage reports
- Monitor reviews
- View platform statistics

Admin can use a Laravel web dashboard. A mobile admin interface is not required for MVP.

---

# 6. Customer Mobile Navigation

Bottom navigation:

```text
Home
Explore
Orders
Impact
Profile
```

## Home

Show:

- Greeting
- Current location
- Search
- Categories
- Nearby food
- Ending soon
- Popular food
- Favorite partners

Example:

```text
Good afternoon 👋

📍 Denpasar

[ Search food... ]

Food near you

Ending soon

Popular nearby
```

---

# 7. Food Discovery

Users can search by:

- Food name
- Partner name
- Category

Filters:

- Distance
- Price
- Category
- Rating
- Pickup time
- Discount

Sorting:

- Nearest
- Cheapest
- Highest discount
- Ending soon
- Highest rated

Use API pagination.

Example:

```text
GET /api/v1/foods?search=chicken&page=1
```

---

# 8. Food Card

Each card should show:

- Food image
- Food name
- Partner name
- Rescue price
- Original price
- Discount percentage
- Stock
- Distance
- Rating
- Pickup time

Example:

```text
Chicken Rice

Rp12.000
~~Rp25.000~~

52% OFF

⭐ 4.8
📍 1.2 km
5 left

Pickup:
18:00 - 20:00
```

---

# 9. Food Detail

Include:

- Food image
- Name
- Description
- Category
- Partner
- Original price
- Rescue price
- Discount
- Stock
- Pickup time
- Location
- Distance
- Rating
- Reviews
- Favorite button

Primary CTA:

```text
ORDER NOW
```

---

# 10. Order System

Customer selects quantity.

Backend must validate:

- Quantity > 0
- Quantity <= stock
- Food is AVAILABLE
- Pickup window has not ended
- Partner is active/approved

The Flutter client must never be trusted for price, stock, role, or total calculation.

Stock reduction must happen inside a database transaction and be safe against race conditions.

---

# 11. Order Status

Use:

```text
PENDING
CONFIRMED
PREPARING
READY_FOR_PICKUP
COMPLETED
CANCELLED
EXPIRED
```

Normal flow:

```text
PENDING
   ↓
CONFIRMED
   ↓
PREPARING
   ↓
READY_FOR_PICKUP
   ↓
COMPLETED
```

Alternative:

```text
PENDING → CANCELLED
PENDING → EXPIRED
```

State transitions must be validated server-side.

---

# 12. Pickup System

FoodRescue is pickup-first.

After an order becomes `READY_FOR_PICKUP`, the customer receives a QR code.

Partner scans the QR code.

Backend validates:

- QR/order exists
- Order belongs to the customer
- Order belongs to the partner
- Order is not already completed
- Order is not expired
- Pickup is valid

After successful verification:

```text
READY_FOR_PICKUP → COMPLETED
```

A QR pickup code must never be reusable.

---

# 13. Pickup Window

Each food has:

```text
pickup_start
pickup_end
```

Example:

```text
18:00
20:00
```

After `pickup_end`:

- Food can become `EXPIRED`
- Unclaimed orders can become `EXPIRED`

Use timezone:

```text
Asia/Makassar
```

All server-side time calculations must use a consistent timezone strategy.

---

# 14. Location

Partner stores:

```text
latitude
longitude
```

Customer location is used for:

- Nearby food
- Distance
- Sorting
- Map display

Do not permanently track the user's location unless a future feature specifically requires it.

Distance must be calculated by the backend or database strategy. Do not trust a distance value sent from Flutter.

Example:

```text
Customer
-8.65, 115.21

Partner
-8.66, 115.20

Distance
1.2 km
```

---

# 15. Map

Food detail should provide:

```text
VIEW ON MAP
```

Show:

- Partner location
- Customer location when appropriate
- Distance

Do not expose private personal addresses. Store/display business addresses for partners.

---

# 16. Partner Dashboard

Show:

```text
Today's Orders
Today's Revenue
Food Rescued
Active Foods
```

Example:

```text
Orders
18

Revenue
Rp420.000

Food Rescued
32

Active Foods
8
```

---

# 17. Add Food

Partner form:

- Food image
- Food name
- Category
- Description
- Original price
- Rescue price
- Stock
- Pickup start
- Pickup end

Validation:

```text
rescue_price < original_price
stock > 0
pickup_end > pickup_start
image required
category required
```

Discount must be calculated automatically.

---

# 18. Food Status

Use:

```text
DRAFT
AVAILABLE
SOLD_OUT
EXPIRED
INACTIVE
```

Do not hard-delete food that already has order history. Prefer soft deletes where appropriate.

---

# 19. Partner Order Management

Partner can see:

```text
Pending
Confirmed
Preparing
Ready for Pickup
Completed
Cancelled
```

Only valid state transitions are allowed.

Partner may only manage orders associated with their own business.

---

# 20. Favorites

Customers can favorite:

- Food
- Partner

Provide:

```text
My Favorites
```

---

# 21. Reviews

A customer can review only an order with:

```text
COMPLETED
```

Review fields:

```text
rating: 1-5
comment
optional image
```

One order can have at most one review.

---

# 22. Impact System

This is a key FoodRescue feature.

## Customer Impact

Example:

```text
MY IMPACT

🍱
18
Food Rescued

💰
Rp245.000
Money Saved

📦
12
Orders Completed
```

Only completed orders count.

Cancelled and expired orders must not count as rescued food.

## Partner Impact

```text
BUSINESS IMPACT

🍱
342
Food Rescued

💰
Rp4.200.000
Revenue Recovered
```

---

# 23. Discount Calculation

Example:

```text
Original Price = Rp25.000
Rescue Price = Rp12.000
```

Discount:

```text
((25.000 - 12.000) / 25.000) × 100
= 52%
```

Backend calculates this automatically.

Never require the client to submit the final discount.

---

# 24. Notifications

Customer notifications:

- Order confirmed
- Order ready
- Pickup reminder
- Order completed
- Order cancelled

Partner notifications:

- New order
- Order cancelled
- Pickup completed

MVP can use database notifications.

Push notifications can use Firebase Cloud Messaging.

---

# 25. Database

Minimum tables:

```text
users
partners
categories
foods
orders
order_items
favorites
reviews
addresses
notifications
```

Potential future tables:

```text
payments
reports
device_tokens
```

---

# 26. Database Relationships

```text
User
 ├── Orders
 ├── Reviews
 ├── Favorites
 └── Addresses

Partner
 ├── User
 ├── Foods
 └── Orders

Food
 ├── Partner
 ├── Category
 └── Order Items

Order
 ├── User
 ├── Partner
 └── Order Items

OrderItem
 ├── Order
 └── Food

Review
 ├── User
 ├── Order
 └── Food
```

---

# 27. Suggested Database Fields

## users

```text
id
name
email
password
role
phone
avatar
email_verified_at
created_at
updated_at
```

Roles:

```text
CUSTOMER
PARTNER
ADMIN
```

## partners

```text
id
user_id
business_name
description
phone
address
latitude
longitude
logo
status
verified_at
created_at
updated_at
```

Status:

```text
PENDING
APPROVED
REJECTED
SUSPENDED
```

## categories

```text
id
name
slug
image
created_at
updated_at
```

## foods

```text
id
partner_id
category_id
name
slug
description
image
original_price
rescue_price
stock
pickup_start
pickup_end
status
created_at
updated_at
deleted_at
```

## orders

```text
id
user_id
partner_id
order_number
subtotal
total
status
payment_status
pickup_code
pickup_completed_at
expires_at
created_at
updated_at
```

## order_items

```text
id
order_id
food_id
quantity
unit_price
subtotal
created_at
updated_at
```

Important: `unit_price` should preserve the price at the time of purchase.

## favorites

```text
id
user_id
food_id
created_at
updated_at
```

A future implementation can support partner favorites with a polymorphic relationship.

## reviews

```text
id
user_id
order_id
food_id
rating
comment
image
created_at
updated_at
```

## addresses

```text
id
user_id
label
address
latitude
longitude
created_at
updated_at
```

---

# 28. API Design

Base URL:

```text
/api/v1
```

## Authentication

```text
POST /auth/register
POST /auth/login
POST /auth/logout
GET  /auth/me
```

## Foods

```text
GET    /foods
GET    /foods/{id}
POST   /foods
PUT    /foods/{id}
DELETE /foods/{id}
```

## Categories

```text
GET /categories
```

## Orders

```text
GET  /orders
GET  /orders/{id}
POST /orders
POST /orders/{id}/cancel
```

## Partner Orders

```text
GET   /partner/orders
PATCH /partner/orders/{id}/status
POST  /partner/orders/{id}/pickup
```

## Partner Foods

```text
GET    /partner/foods
POST   /partner/foods
PUT    /partner/foods/{id}
DELETE /partner/foods/{id}
```

## Reviews

```text
GET  /foods/{id}/reviews
POST /orders/{id}/review
```

## Favorites

```text
GET    /favorites
POST   /favorites
DELETE /favorites/{id}
```

## Impact

```text
GET /impact
GET /partner/impact
```

## Partner

```text
GET  /partner/profile
POST /partner/profile
GET  /partner/dashboard
```

---

# 29. API Response Format

Success:

```json
{
    "success": true,
    "message": "Food retrieved successfully",
    "data": {}
}
```

Error:

```json
{
    "success": false,
    "message": "Something went wrong",
    "errors": {}
}
```

Use appropriate HTTP status codes:

```text
200 OK
201 Created
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
422 Unprocessable Entity
500 Server Error
```

---

# 30. Security

Use:

```text
Laravel Sanctum
auth:sanctum
Policies
Gates
Form Requests
```

Rules:

1. Customer cannot access partner management endpoints.
2. Partner cannot access admin endpoints.
3. Partner can only edit its own food.
4. Customer can only access its own orders.
5. Do not trust role from request body.
6. Do not trust price from Flutter.
7. Do not trust stock from Flutter.
8. Do not trust total from Flutter.
9. Validate all server-side input.
10. Never store plaintext passwords.
11. Do not expose stack traces to users.
12. Protect file uploads.
13. Rate-limit sensitive endpoints where appropriate.
14. Use HTTPS in production.

---

# 31. Flutter Architecture

Suggested structure:

```text
lib/

core/
    constants/
    theme/
    routes/
    network/
    storage/
    utils/

features/
    auth/
        data/
        models/
        repositories/
        screens/
        widgets/

    home/
        data/
        models/
        repositories/
        screens/
        widgets/

    foods/
        data/
        models/
        repositories/
        screens/
        widgets/

    orders/
        data/
        models/
        repositories/
        screens/
        widgets/

    partner/
        data/
        models/
        repositories/
        screens/
        widgets/

    profile/
    impact/

shared/
    widgets/
```

Keep business logic outside UI widgets.

---

# 32. State Management

Use Riverpod for:

- Authentication
- Food list
- Food detail
- Orders
- Partner dashboard
- Location
- Profile
- Impact

Avoid global mutable static variables.

---

# 33. HTTP Client

Use Dio.

Create:

```text
ApiClient
```

Repositories:

```text
AuthRepository
FoodRepository
OrderRepository
PartnerRepository
ReviewRepository
FavoriteRepository
ImpactRepository
```

Use interceptors for:

- Authentication token
- Development logging
- Error handling
- Token expiration handling

Keep API base URL configurable.

---

# 34. Local Storage

Store:

- Authentication session/token
- Non-sensitive local preferences

Use secure storage for authentication tokens when appropriate.

Never store passwords.

---

# 35. UI / UX

Design direction:

- Modern
- Clean
- Friendly
- Food-focused
- Sustainable
- Simple
- Easy to navigate

Use:

- Rounded cards
- Clear typography
- Strong price hierarchy
- Food imagery
- Bottom navigation
- Skeleton loading
- Empty states
- Error states
- Confirmation dialogs

Avoid excessive animations.

---

# 36. Visual Identity

Suggested color direction:

Primary:
- Green

Secondary:
- Orange/warm accent

Background:
- Off-white/light neutral

The green should communicate sustainability and the warm accent should communicate food and urgency.

Dark mode can be added after MVP.

---

# 37. Mobile Responsiveness

Target Android first.

Support:

- Small phones
- Normal phones
- Large phones

Use:

```text
SafeArea
Responsive layouts
Flexible widgets
```

Do not hardcode screen dimensions.

---

# 38. Error Handling

Handle:

- No internet
- Server error
- Timeout
- Unauthorized
- Expired token
- Location permission denied
- Location unavailable
- Empty results
- Food sold out
- Food expired
- Order failure

Messages must be understandable.

Never display stack traces.

---

# 39. Admin Dashboard

Admin web dashboard:

```text
Dashboard
Users
Partners
Partner Verification
Foods
Categories
Orders
Reviews
Reports
```

Statistics:

```text
Total Users
Total Partners
Total Orders
Completed Orders
Food Rescued
Revenue
```

---

# 40. Partner Verification

Partners must be verified before selling.

Statuses:

```text
PENDING
APPROVED
REJECTED
SUSPENDED
```

Only `APPROVED` partners can publish food.

---

# 41. Payment

Do not implement an online payment gateway in MVP.

Use:

```text
PAY AT PICKUP
```

Prepare database support for future payment integration:

```text
payment_status

PENDING
PAID
FAILED
REFUNDED
```

Future payment gateway options can be integrated later.

---

# 42. Business Rules

1. Rescue price must be lower than original price.
2. Stock cannot be negative.
3. Expired food cannot be ordered.
4. Sold-out food cannot be ordered.
5. Partner must be approved before publishing food.
6. Partner can only manage its own food.
7. Customer can only access its own orders.
8. Review is only allowed after completion.
9. QR pickup can only be used once.
10. Completed order requires successful pickup verification.
11. Order creation uses database transaction.
12. Stock reduction must prevent race conditions.
13. Backend calculates order total.
14. Backend calculates discount.
15. Backend determines authorization.
16. Cancelled and expired orders do not count as rescued food.
17. Only completed orders count toward impact statistics.

---

# 43. Testing Strategy

## Backend

Test:

1. Registration
2. Login
3. Sanctum authentication
4. Role authorization
5. Partner verification
6. Food creation
7. Food update
8. Food ownership
9. Order creation
10. Stock reduction
11. Stock race condition protection
12. Expired food validation
13. Order status transitions
14. QR pickup
15. QR reuse prevention
16. Review authorization
17. Impact calculation

## Flutter

Test:

- Login screen
- Home screen
- Food card
- Food detail
- Order flow
- Order state
- Partner screens
- Empty state
- Error state

---

# 44. Environment Requirements

Backend:

```env
APP_NAME=FoodRescue
APP_ENV=local
APP_URL=http://foodrescue.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=foodrescue
DB_USERNAME=root
DB_PASSWORD=
```

Flutter:

```text
API_BASE_URL
MAP_API_KEY
```

Do not hardcode API keys into source code.

For Android emulator, the Laravel host machine is usually reachable using:

```text
10.0.2.2
```

For a physical Android device, use the development computer's LAN IP when testing locally.

---

# 45. Recommended Flutter Packages

Exact versions should be selected based on compatibility at implementation time.

Core:

```text
flutter_riverpod
dio
go_router
flutter_secure_storage
shared_preferences
```

Location/maps:

```text
geolocator
google_maps_flutter
```

Media/QR:

```text
image_picker
mobile_scanner
```

Notifications:

```text
firebase_core
firebase_messaging
```

Utility:

```text
intl
cached_network_image
```

Only add packages when they solve a real requirement.

---

# 46. Laravel Packages / Components

Prefer Laravel's native features first.

Core:

```text
laravel/sanctum
```

Use Laravel native:

- Eloquent
- Form Requests
- API Resources
- Policies
- Gates
- Notifications
- Queues when needed
- Storage
- Scheduler
- Task scheduling

Avoid adding packages unnecessarily.

---

# 47. Development Phases

## Phase 1 — Foundation

- Laravel project
- Flutter project
- MySQL
- Environment
- Git repository
- Authentication
- User roles
- Sanctum
- Database migrations

## Phase 2 — Food System

- Categories
- Partner profile
- Food CRUD
- Food images
- Food status
- Pricing
- Stock

## Phase 3 — Order System

- Create order
- Order items
- Stock transaction
- Order status
- Order history
- Cancellation
- Expiration

## Phase 4 — Flutter Customer

- Theme
- Routing
- Authentication
- Home
- Explore
- Food detail
- Order flow
- Profile

## Phase 5 — Partner

- Partner dashboard
- Add food
- Manage food
- Order management
- Partner profile
- Impact statistics

## Phase 6 — Location

- GPS
- Permission handling
- Distance calculation
- Nearby food
- Map

## Phase 7 — Pickup

- QR generation
- QR scanning
- Pickup verification
- Completion

## Phase 8 — Engagement

- Favorites
- Reviews
- Notifications
- Impact dashboard

## Phase 9 — Admin

- Admin dashboard
- Partner verification
- User management
- Food management
- Order monitoring
- Reports

## Phase 10 — Quality

- Tests
- Security audit
- Performance
- Error handling
- UI polish
- Documentation
- Production preparation

---

# 48. Development Rules for AI Coding Assistant

Do not generate the entire application in one step.

Before coding:

1. Analyze requirements.
2. Design architecture.
3. Create ERD.
4. Define database schema.
5. Define API endpoints.
6. Define role permissions.
7. Define user flows.
8. Define folder structure.
9. Define development roadmap.

Then implement phase by phase.

After each phase:

1. Explain what was created.
2. List files changed.
3. Explain how to run it.
4. Explain how to test it.
5. Identify known limitations.
6. Wait for approval before moving to the next major phase.

Never remove working functionality just to implement a new feature.

Do not change architecture without explaining why.

When requirements are ambiguous, explain the options and recommend a technical approach before implementation.

Priorities:

```text
Security
Data Integrity
Maintainability
Performance
Clean Architecture
Good UX
```

---

# 49. Initial AI Task

When this specification is first provided to an AI coding assistant, DO NOT immediately write the complete application.

First produce:

1. System architecture
2. Complete tech stack
3. Laravel folder structure
4. Flutter folder structure
5. Text-based ERD
6. Complete database tables and fields
7. Complete API endpoint list
8. Role permission matrix
9. Customer flow
10. Partner flow
11. Admin flow
12. Order flow
13. Pickup flow
14. Development roadmap
15. Required packages/dependencies
16. Environment requirements
17. Security considerations
18. Testing strategy

After presenting the design, wait for approval.

Only then begin Phase 1.
