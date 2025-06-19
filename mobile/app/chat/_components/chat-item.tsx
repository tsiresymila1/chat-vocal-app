import { Text } from "@/components/ui/text";
import { Ionicons } from "@expo/vector-icons";
import { router } from "expo-router";
import { FC } from "react";
import { TouchableOpacity, View } from "react-native";

export type ChatPreview = {
    id: string;
    title: string;
    lastMessage: string;
    timestamp: string;
}

 const ChatItem: FC<ChatPreview> = ({ id, title, lastMessage, timestamp }) => {
    return <TouchableOpacity
        onPress={() => router.push(`/chat/${id}`)}
        className="flex-row items-center p-4 border-b dark:border-gray-800 border-gray-200"
    >
        <View className="w-12 h-12 rounded-full dark:bg-blue-900/50 bg-blue-100 items-center justify-center mr-4">
            <Ionicons name="chatbubble" size={24} color="#3b82f6" />
        </View>
        <View className="flex-1">
            <Text className="font-semibold text-lg">{title}</Text>
            <Text className="" numberOfLines={1}>
                {lastMessage}
            </Text>
        </View>
        <Text className=" text-sm">{timestamp}</Text>
    </TouchableOpacity>
}

export default ChatItem;