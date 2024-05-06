## 🚀 Consumer Service Documentation

### 📝 Overview

The Consumer Service is responsible for consuming messages from RabbitMQ queues and processing them according to the external events defined. It includes several components such as consumers, command handlers, and helper classes.

### 🛠️ Components

1. **ConsumerCommand**
    - **Description:** Command-line interface for consuming messages from RabbitMQ queues.
    - **Usage:** Run the command `rabbitmq:consumer {exchange}` to start consuming messages from the specified exchange.

2. **RetryConsumerCommand**
    - **Description:** Command for retrying consumption of messages from the Redis retry queue.
    - **Usage:** Run the command `retry:consume` to retry consuming messages from the retry queue.

3. **QueueConsumer**
    - **Description:** Abstract class for implementing specific queue consumers.
    - **Usage:** Extend this class to create custom queue consumers for different events.
----

### 🚀 Usage

#### Consumer Command

1. **Command Syntax:** `rabbitmq:consumer {exchange}`
    - **Example:** `php artisan rabbitmq:consumer user_events`
    - **Description:** Start consuming messages from the specified RabbitMQ exchange.

#### Retry Consumer Command

1. **Command Syntax:** `retry:consume`
    - **Example:** `php artisan retry:consume`
    - **Description:** Retry consuming messages from the Redis retry queue.

#### Queue Consumer Classes

1. **OnUserCreateQueueConsumer**
    - **Description:** Process user creation events by fetching data from source APIs, transforming the payload, and sending it to the base table for new user addition.

2. **AccountPaymentQueueConsumer**
    - **Description:** Process account payment events by fetching account details from the source API and updating the account status and expiry date in the base table.

3. **CustomerInfoModificationQueueConsumer** 
    - **Description:** Process customer info modification events by fetching customer details from source APIs and updating customer information in the base table.

4. **PlanMigrationQueueConsumer**
    - **Description:** Process plan migration events by retrieving plan details from the source API and updating the user's plan information in the base table.
----
### ⚙️ Configuration

1. **RabbitMQ Configuration**
    - Ensure that RabbitMQ connection details are properly configured in the `.env` file.

2. **Redis Configuration**
    - Make sure Redis connection details are correctly set up in the `.env` file.

3. **Service Configurations**
    - Update service-specific configurations such as API endpoints
----
### 📋 Logging

1. **Log Messages**
    - Log messages are generated for various events such as successful message processing, errors, and retries.
    - Logs can be found in the configured log files for monitoring and troubleshooting purposes.
----
### ❌ Error Handling

1. **Retry Mechanism**
    - If an error occurs during message processing, the retry mechanism automatically retries consuming the message from the retry queue.

2. **Error Logging**
    - Errors and exceptions are logged with detailed information including queue names, usernames, and error messages for debugging purposes.
----
### 📦 Dependencies

1. **PhpAmqpLib**
    - Dependency for RabbitMQ connection and message handling.

2. **GuzzleHttp**
    - Dependency for making HTTP requests to source and destination APIs.

3. **Carbon**
    - Dependency for date/time manipulation.
----
### 🌟 Example Code

```php
// Example of consuming messages from RabbitMQ
php artisan rabbitmq:consumer external_events

// Example of retrying message consumption from Redis retry queue
php artisan retry:consume
