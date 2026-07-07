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
- Supervisor

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

2. Copy and Configure the environment file for backend:

   ```bash
   cp backend/.env.example backend/.env
   ```

3. Configure the environment file for frontend:

   ```bash
   cd frontend
   ```

4. Build and start the Docker containers:
   
   ```bash
   docker compose up --build -d
   ```

5. Run composer install command:
   
   ```bash
   docker compose run --rm backend composer install
   ```

6. Restart the backend container:
   
   ```bash
   docker compose restart backend
   ```

8. Generate the Laravel application key:

   ```bash
   docker compose exec -it backend bash
   php artisan key:generate
   ```

7. Run database migrations:

   ```bash
   docker compose exec -it backend bash
   php artisan migrate
   ```

8. Seed the database:

   ```bash
   docker compose exec -it backend bash
   php artisan db:seed
   ```

9. Access the application:

   ```bash
   http://localhost:5173
   ```
