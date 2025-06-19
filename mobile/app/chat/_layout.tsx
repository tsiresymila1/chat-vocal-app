import AuthRequired from "@/components/auth-required";
import { Stack } from "expo-router";

export default function ChatLayout() {
    return <AuthRequired>
        <Stack screenOptions={{ headerShown: false }} />
    </AuthRequired>
}