import React from 'react';
import { View } from 'react-native';
import RegisterForm from './_components/register-form';

export default function RegisterScreen() {
  return (
    <View className="flex-1 p-8">
      <RegisterForm />
    </View>
  );
} 