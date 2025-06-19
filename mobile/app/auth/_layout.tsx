import { ToggleThemeButton } from "@/components/toogle-theme-button";
import { Stack } from "expo-router";
import { View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

export default function AuthLayout() {
    return <SafeAreaView className="w-full h-full flex">
        <View className="flex flex-row justify-end px-4">
            <ToggleThemeButton />
        </View>
        <View className="flex-1">
            <Stack screenOptions={{ headerShown: false }} />
        </View>
    </SafeAreaView>
}