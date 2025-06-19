import { useThemeStore } from "@/store/theme";
import { Ionicons } from "@expo/vector-icons";
import { FC } from "react";
import { TouchableOpacity } from "react-native";

export const ToggleThemeButton: FC = () => {
    const { isDark, toggle } = useThemeStore()
    return <TouchableOpacity
        onPress={toggle}
        className="p-2"
    >
        <Ionicons name={isDark ? 'sunny' : 'moon'} size={24} color="#3b82f6" />
    </TouchableOpacity>
}