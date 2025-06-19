import { useAuthStore } from "@/store/auth";
import { Redirect } from "expo-router";

export default function IndexScreen() {
    const {isAuthenticated} = useAuthStore()
    if (isAuthenticated) {
        return <Redirect href="/chat" />
    }
    return <Redirect href="/auth/login" />
}