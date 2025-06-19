import AsyncStorage from "@react-native-async-storage/async-storage";
import { create } from "zustand";
import { createJSONStorage, persist } from "zustand/middleware";

type ThemeState = {
    isDark: boolean
    toggle: () => void;
}

export const useThemeStore = create<ThemeState>()(
    persist(
        (set) => ({
            isDark: false,
            toggle: () =>
                set((state) => ({
                    isDark: !state.isDark
                })),

        }),
        {
            name: "theme-storage",
            storage: createJSONStorage(() => ({
                getItem: AsyncStorage.getItem,
                setItem: AsyncStorage.setItem,
                removeItem: AsyncStorage.removeItem,
            })),
        }
    )
);
