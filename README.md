# Metronet
## ISP Management System

System for ISP

## Features

### Admin
- Register/ Login/ Logout
- Customer Management
- ISP plan Management
- CPE management
- Service Area Management

### Customer
- Register/ Login/ Logout
- ISP plans
-  Subscriptions
-  Invoices
-  Billing
-  Payment
-  Notifications

### Tech
- Laravel
- MySQL
- Redis
- Kafka
- Docker

### Workflow
- Customer subscribes to a plan.
- Subscription is processed using a job.
- CPE is assigned automatically.
- Invoice is created via kafka.
- Payment is processed.
- Notification is sent to the customer.
