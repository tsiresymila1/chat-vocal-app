# Welcome to your Expo app 👋

This is an [Expo](https://expo.dev) project created with [`create-expo-app`](https://www.npmjs.com/package/create-expo-app).

## Get started

1. Install dependencies

   ```bash
   yarn install
   ```

2. Start the app

   ```bash
   yarn android
   ```

In the output, you'll find options to open the app in a

- [development build](https://docs.expo.dev/develop/development-builds/introduction/)
- [Android emulator](https://docs.expo.dev/workflow/android-studio-emulator/)
- [iOS simulator](https://docs.expo.dev/workflow/ios-simulator/)
- [Expo Go](https://expo.dev/go), a limited sandbox for trying out app development with Expo

You can start developing by editing the files inside the **app** directory. This project uses [file-based routing](https://docs.expo.dev/router/introduction).

## Exposing Laravel API for Real Devices

If you are running the mobile app on a real device, you need to expose your local Laravel API to the internet. You can use [ngrok](https://ngrok.com/) for this purpose.

### Steps:

1. Start your Laravel backend (usually in the `backend` directory):

   ```bash
   php artisan serve
   ```

2. In a new terminal, start ngrok to expose your Laravel API (replace 8000 with your Laravel port if different):

   ```bash
   ngrok http 8000
   ```

3. Copy the HTTPS URL provided by ngrok (e.g., `https://xxxxxx.ngrok.io`).

4. In your mobile app, configure the API base URL to use the ngrok URL. You can usually do this in `lib/api/client.ts` or a similar configuration file.

   Example:
   ```ts
   // lib/api/client.ts
   export const API_BASE_URL = 'https://xxxxxx.ngrok.io';
   ```

## Get a fresh project

When you're ready, run:

```bash
npm run reset-project
```

This command will move the starter code to the **app-example** directory and create a blank **app** directory where you can start developing.

## Learn more

To learn more about developing your project with Expo, look at the following resources:

- [Expo documentation](https://docs.expo.dev/): Learn fundamentals, or go into advanced topics with our [guides](https://docs.expo.dev/guides).
- [Learn Expo tutorial](https://docs.expo.dev/tutorial/introduction/): Follow a step-by-step tutorial where you'll create a project that runs on Android, iOS, and the web.

## Join the community

Join our community of developers creating universal apps.

- [Expo on GitHub](https://github.com/expo/expo): View our open source platform and contribute.
- [Discord community](https://chat.expo.dev): Chat with Expo users and ask questions.
