# Chat vocal

A mobile application built with **React Native (Expo)** and a backend powered by **Laravel**.

[Watch the demo](https://drive.google.com/file/d/19GG7L_n_DIs2HJS-DqkfvR8q-GrJD4_e/view?usp=sharing)

---

## Table of Contents

- [Prerequisites](#prerequisites)
- [Project Structure](#project-structure)
- [Setup](#mise-en-route-setup)
  - [1. Backend (Laravel)](#1-backend-laravel)
  - [2. Frontend (React Native Expo)](#2-frontend-react-native-expo)
  - [3. Exposing Laravel API for Real Devices (ngrok)](#3-exposing-laravel-api-for-real-devices-ngrok)
- [Development Workflow](#development-workflow)
- [Useful Links](#useful-links)

---

## Prerequisites

- Node.js (v16+ recommended)
- Yarn or npm
- PHP (v8.1+ recommended)
- Composer
- MySQL or PostgreSQL (or another supported DB)
- [Expo CLI](https://docs.expo.dev/get-started/installation/)
- [Laravel](https://laravel.com/docs/installation)
- Android Studio / Xcode (for emulators/simulators)
- [ngrok](https://ngrok.com/) (for real device API access)
- Docker

---

## Project Structure

```
root/
  mobile/           # React Native (Expo) app
  backend/          # Laravel API
```

---

## Mise en route (Setup)

### 1. Backend (Laravel)

#### Using docker

- #### Copy the example environment

```sh
cp .env.example .env
```

Edit `.env` if needed (especially database credentials).

- #### Build and start the containers

```sh
docker-compose up --build -d
```

- #### Install Composer dependencies

```sh
docker-compose exec app composer install
```

- #### Generate the application key

```sh
docker-compose exec app php artisan key:generate
```

- #### Run migrations

```sh
docker-compose exec app php artisan migrate
```

- #### Seed default chat

```sh
docker-compose exec app php artisan db:seed
```

- #### Access the app

  - The API will be available at [http://localhost:8000](http://localhost:8000)
  - PostgreSQL will be available at port `5434` (see `.env` for credentials)

#### Without docker

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
# Configure your .env (DB, etc.)
php artisan migrate
php artisan serve
```

- The API will be available at [http://localhost:8000](http://localhost:8000)

### 2. Frontend (React Native Expo)

```bash
cd mobile

cp .env.example .env

yarn install
# or
npm install

# Start the Expo app
yarn android   # For Android
yarn ios       # For iOS
yarn start     # For Expo Go or web
```

- Configure the API base URL in your mobile app to point to your Laravel backend (see `lib/api/client.ts` or similar).

### 3. Exposing Laravel API for Real Devices (ngrok)

If you are running the mobile app on a real device, you need to expose your local Laravel API to the internet using ngrok:

```bash
ngrok http 8000
```

- Copy the HTTPS URL provided by ngrok (e.g., `https://xxxxxx.ngrok.io`).
- In your mobile app, set `EXPO_PUBLIC_API_BASE_URL` in `mobile/.env` to this ngrok URL (see `lib/api/client.ts`).

---

## Development Workflow

- **Backend**: Make changes in `backend/`, use `php artisan serve` to run the API.
- **Frontend**: Make changes in `mobile/`, use Expo commands to run the app on your device or emulator.

---

## Author

- ### [Tsiresy Milà](https://tsiresymila.vercel.app/)
