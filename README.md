# Metronet
## ISP Management System

System for ISP Management

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

### Tech Stack
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
docker compose up --build -d
```

4. Access the application:

```bash
http://localhost:8080
```
