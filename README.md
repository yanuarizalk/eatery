# Eatery API

This is the backend API for the Eatery application, a platform for discovering restaurants, viewing menus, and reading reviews. It also features a Telegram bot for easy interaction.

## Technology Stack

*   **Backend:** Laravel (PHP)
*   **Database:** Postgre
*   **Web Server:** Nginx
*   **Containerization:** Docker

## Features

*   **User Authentication:** Secure user registration and login with JWT and Two-Factor Authentication (2FA).
*   **Restaurant Discovery:** Search for restaurants, filter by cuisine, rating, price level, and find nearby places using the Google Maps API.
*   **Menus and Reviews:** View detailed menus and user-submitted reviews for each restaurant.
*   **API Request Logging:** All API requests are logged for tracing and analysis.
*   **Telegram Bot:** Interact with the API through a simple Telegram bot.

## Getting Started

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/yanuarizalk/eatery.git
    cd eatery
    ```

2.  **Set up your environment:**
    *   Copy the `.env.example` file to `.env`.
    *   Update the `.env` file with your database credentials, Google Maps API key, and Telegram bot token.

3.  **Build and run with Docker:**
    ```bash
    docker-compose up -d --build
    ```

4.  **Run database migrations:**
    ```bash
    docker-compose exec app php artisan migrate
    ```

## API Endpoints

A complete collection of API endpoints is available on Postman. You can access it here:

[Eatery API Postman Collection](https://postman.yanuarizal.net/11658621-e1e20aba-4403-46a4-bbf3-55fcbc7b4bc9?action=share&source=copy-link&creator=11658621)

## Telegram Bot

You can interact with the Eatery API through our Telegram bot.

*   **Bot Link:** [t.me/PopinaMaBot](https://t.me/PopinaMaBot)
*   **Available Commands:**
    *   `/start` - Initialize the bot.
    *   `/help` - List all command.
    *   `<restaurant name>` - Search for a restaurant.

## Running Tests

To run the full suite of tests for the application, use the following command:

```bash
docker-compose exec app php artisan test
