import AsyncStorage from "@react-native-async-storage/async-storage";
import { create } from "zustand";
import { createJSONStorage, persist } from "zustand/middleware";

type UserValue = string | number | boolean;
type UserType = Record<string, UserValue | Record<string, UserValue>>;

type AuthState = {
    user?: UserType;
    token?: string;
    isAuthenticated: boolean;
    login: (user: UserType, token: string) => void;
    logout: () => void;
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set) => ({
            isAuthenticated: false,
            login: (user: UserType, token: string) =>
                set(() => ({
                    user,
                    token,
                    isAuthenticated: true,
                })),
            logout: () =>
                set(() => ({
                    user: undefined,
                    isAuthenticated: false,
                    token: undefined,
                })),
        }),
        {
            name: "auth-storage",
            storage: createJSONStorage(() => ({
                getItem: AsyncStorage.getItem,
                setItem: AsyncStorage.setItem,
                removeItem: AsyncStorage.removeItem,
            })),
        }
    )
);
