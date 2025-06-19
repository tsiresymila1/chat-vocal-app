import React from 'react';
import { View, TouchableOpacity, Alert, KeyboardAvoidingView, Platform } from 'react-native';
import { router } from 'expo-router';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { loginSchema, type LoginFormData } from '../schemas';
import { $api } from '@/lib/api/client';
import { useAuthStore } from '@/store/auth';

const LoginForm = () => {
  const {
    control,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
    defaultValues: {
      email: '',
      password: '',
    },
  });
  const { login: loginToStore } = useAuthStore()
  const { mutateAsync: login } = $api.useMutation("post", "/auth/login")

  const onSubmit = async (data: LoginFormData) => {
    try {
      const res = await login({
        body: data
      })
      if (res.access_token && res.user) {
        loginToStore(res.user, res.access_token)
      }
      router.replace('/');
    } catch (error) {
      Alert.alert('Login failed');
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 80 : 0}
      className='w-full h-full flex-1'>
      <View className="flex-1 justify-center gap-3">
        <Text className="text-3xl font-bold text-center mb-8">Welcome Back</Text>

        <Controller
          control={control}
          name="email"
          render={({ field: { onChange, value } }) => (
            <Input
              placeholder="Enter your email"
              value={value}
              onChangeText={onChange}
              autoCapitalize="none"
              keyboardType="email-address"
              className="mb-1"
              error={errors.email?.message}
            />
          )}
        />

        <Controller
          control={control}
          name="password"
          render={({ field: { onChange, value } }) => (
            <Input
              placeholder="Enter your password"
              value={value}
              onChangeText={onChange}
              secureTextEntry
              className="mb-6"
              error={errors.password?.message}
            />
          )}
        />

        <Button
          onPress={handleSubmit(onSubmit)}
          className="mb-4"
          variant="default"
          disabled={isSubmitting}
        >
          <Text>{isSubmitting ? 'Signing in...' : 'Sign In'}</Text>
        </Button>
        <TouchableOpacity
          onPress={() => router.push('/auth/register')}
          className="items-center"
        >
          <Text className="text-blue-500">
            Don't have an account? Sign up
          </Text>
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  );
}

export default LoginForm;
