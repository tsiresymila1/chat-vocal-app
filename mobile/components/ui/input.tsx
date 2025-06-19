import * as React from 'react';
import { TextInput, type TextInputProps, View } from 'react-native';
import { cn } from '@/lib/utils';
import { Text } from './text';

interface InputProps extends TextInputProps {
  ref?: React.RefObject<TextInput>;
  error?: string;
}

function Input({
  className,
  placeholderClassName,
  error,
  ...props
}: InputProps) {
  return (
    <View className="w-full">
      <TextInput
        className={cn(
          'web:flex h-10 native:h-12 web:w-full rounded-md border border-input bg-background px-3 web:py-2 text-base lg:text-sm native:text-lg native:leading-[1.25]  dark:bg-slate-800 placeholder:dark:text-gray-400 text-foreground web:ring-offset-background file:border-0 file:bg-transparent file:font-medium web:focus-visible:outline-none web:focus-visible:ring-2 web:focus-visible:ring-ring web:focus-visible:ring-offset-2',
          props.editable === false && 'opacity-50 web:cursor-not-allowed',
          error && 'border-red-500',
          className
        )}
        placeholderClassName={cn('text-muted-foreground', placeholderClassName)}
        {...props}
      />
      {error && (
        <Text className="text-red-500 text-sm mt-1">{error}</Text>
      )}
    </View>
  );
}

export { Input };
