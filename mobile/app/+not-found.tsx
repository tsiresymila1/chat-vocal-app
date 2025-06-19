import { Text } from '@/components/ui/text';
import { Link, Stack } from 'expo-router';
import React from 'react';
import { View } from 'react-native';

export default function NotFoundScreen() {
  return (
    <>
      <Stack.Screen options={{ title: 'Oops!' }} />
      <View className='flex-1 justify-center items-center p-6'>
        <Text>This screen does not exist.</Text>
        <Link href="/" className='mt-5 py-5'>
          <Text>Go to home screen!</Text>
        </Link>
      </View>
    </>
  );
}

