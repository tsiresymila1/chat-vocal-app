import { useThemeStore } from '@/store/theme';
import { useColorScheme as useNativewindColorScheme } from 'nativewind';
import { useEffect } from 'react';

export function useColorScheme() {
  const { colorScheme, setColorScheme, toggleColorScheme } = useNativewindColorScheme();
  const { toggle, isDark } = useThemeStore()

  useEffect(() => {
    if (isDark) {
      setColorScheme("dark")
    } else {
      setColorScheme("light")
    }
  }, [isDark])
  return {
    colorScheme: isDark,
    isDarkColorScheme: isDark,
    setColorScheme,
    toggleColorScheme,
  };
}