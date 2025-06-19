import React from 'react';
import { View, TouchableOpacity, Alert } from 'react-native';
import { router } from 'expo-router';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/ui/text';
import { registerSchema, type RegisterFormData } from '../schemas';
import { useAuthStore } from '@/store/auth';
import { $api } from '@/lib/api/client';

const RegisterForm = () => {

    const {
        control,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = useForm<RegisterFormData>({
        resolver: zodResolver(registerSchema),
        defaultValues: {
            name: '',
            email: '',
            password: '',
            confirmPassword: '',
        },
    });
    const { login: loginToStore } = useAuthStore()
    const { mutateAsync: register } = $api.useMutation("post", "/auth/register")
    const onSubmit = async (data: RegisterFormData) => {
        try {
            const res = await register({
                body: {
                    email: data.email,
                    password: data.password,
                    name: data.name,
                    password_confirmation: data.confirmPassword
                }
            })
            if (res.access_token && res.user) {
                loginToStore(res.user, res.access_token)
            }
            router.replace('/');
        } catch (error) {
            Alert.alert('Registration failed');
        }
    };

    return (
        <View className="flex-1 justify-center gap-4">
            <Text className="text-3xl font-bold text-center mb-8">Create Account</Text>
            <Controller
                control={control}
                name="name"
                render={({ field: { onChange, value } }) => (
                    <Input
                        placeholder="Enter your name"
                        value={value}
                        onChangeText={onChange}
                        className="mb-1 "
                        error={errors.name?.message}
                    />
                )}
            />

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
                        className="mb-1"
                        error={errors.password?.message}
                    />
                )}
            />

            <Controller
                control={control}
                name="confirmPassword"
                render={({ field: { onChange, value } }) => (
                    <Input
                        placeholder="Confirm your password"
                        value={value}
                        onChangeText={onChange}
                        secureTextEntry
                        className="mb-6"
                        error={errors.confirmPassword?.message}
                    />
                )}
            />

            <Button
                onPress={handleSubmit(onSubmit)}
                disabled={isSubmitting}
                variant="default"
                className="mb-4"
            >
                <Text>{isSubmitting ? 'Creating account...' : 'Sign Up'}</Text>
            </Button>

            <TouchableOpacity
                onPress={() => router.back()}
                className="items-center"
            >
                <Text className="text-blue-500">
                    Already have an account? Sign in
                </Text>
            </TouchableOpacity>
        </View>
    );
}

export default RegisterForm
