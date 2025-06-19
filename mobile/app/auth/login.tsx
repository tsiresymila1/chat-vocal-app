import React from 'react';
import { View } from 'react-native';
import LoginForm from './_components/login-form';

export default function LoginScreen() {
  return (
    <View className="flex-1 p-8 ">
      <LoginForm />
    </View>
  );
} 