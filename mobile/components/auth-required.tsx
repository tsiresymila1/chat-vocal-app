import { useAuthStore } from "@/store/auth";
import { Redirect } from "expo-router";
import React from "react"

type AuthRequiredProps = Readonly<{
    children: React.ReactNode
}>
const AuthRequired: React.FC<AuthRequiredProps> = ({ children }) => {
    const { isAuthenticated } = useAuthStore();
    if (!isAuthenticated) {
        return <Redirect href="/auth/login" />
    }
    return <>{children}</>;
}

export default AuthRequired;