# Metronet - ISP Management System

System for ISP Management

## Features

- Customer subscribes to a plan.
- Subscription is processed using a job.
- CPE is assigned automatically.
- Invoice is created via Kafka.
- Payment is processed.
- Notification is sent to the customer.

### Admin
- Register/ Login/ Logout
- Customer Management (View and Deactivate customers)
- ISP Plan Management (View, Create, Update and Deactivate ISP plans)
- CPE Management (View, Create, Update and Deactivate CPEs)
- Service Area Management (View, Create, Update and Deactivate service areas)
- Subscriptions Management (View Subscriptions)
- Invoice Management (View Invoices)
- Payment Management (View Payments)

### Customer
- Register/ Login/ Logout
- Browse ISP Plans
- Subscribe to Plans
- Manage Subscriptions (View, Cancel, Resubscribe)
- View Invoices
- Make Payments
- Receive Notifications

### Admin Customer Flow
1. Admin logs into the system.
2. Admin can view all registered customers.
3. Admin can deactivate customer accounts.
4. Admin can manage ISP plans (Create, Update, Deactivate).
5. Admin can manage CPE devices (Create, Update, Deactivate).
6. Admin can manage service areas (Create, Update, Deactivate).
7. Admin can view all subscriptions.
8. Admin can view all invoices.
9. Admin can view all payments.

### Customer Flow
1. Customer registers an account.
2. Customer logs into the system.
3. Customer browses available ISP plans.
4. Customer subscribes to a plan.
5. System processes subscription via job.
6. CPE device is automatically assigned.
7. Invoice is created via Kafka.
8. Customer receives notification.
9. Customer makes payment.
10. Customer receives confirmation notification.

## Tech Stack
- **Backend:** Laravel
- **Frontend:** Vue.js
- **Database:** MySQL
- **Cache:** Redis
- **Message Queue:** Apache Kafka
- **Containerization:** Docker

## Kafka & Supervisor

### Kafka Topics
- `service.activated` - Triggered when subscription is activated
- `invoice.created` - Triggered when invoice is created
- `payment.success` - Triggered when payment is successful
- `subscription.cancelled` - Triggered when subscription is cancelled

### Supervisor Processes
Supervisor runs and monitors the following processes:
- Queue Worker (processes background jobs)
- Kafka Consumers (listen and process Kafka messages)
  - Payment Consumer
  - Notification Consumer
  - Service Activated Consumer
  - Subscription Cancelled Consumer

### Kafka Flow
1. Backend publishes events to Kafka topics.
2. Supervisor manages Kafka consumers.
3. Consumers process events asynchronously.
4. Notifications and emails are sent to customers.
5. Invoice and payment statuses are updated.
   
### Step by step guide to configure the project
1. Clone the repository:

```bash
git clone https://github.com/Yin-Myat-Noe-Lwin/Metronet.git
cd Metronet
```

2. Copy the environment file:

```bash
cp backend/.env.example backend/.env
```
```bash
cp frontend/.env.example frontend/.env
```

3. Build and start the Docker containers:


```bash
docker compose pull
docker compose up -d
```

4. Access the application:

```bash
http://localhost
```

5. Access Kafka UI

```bash
http://localhost:8081
```
