import { ToggleThemeButton } from '@/components/toogle-theme-button';
import { Text } from '@/components/ui/text';
import { $api } from '@/lib/api/client';
import { paths } from '@/lib/api/openapi';
import { useAuthStore } from '@/store/auth';
import { useThemeStore } from '@/store/theme';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import React, { useCallback } from 'react';
import { Alert, FlatList, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import ChatItem from './_components/chat-item';

export type Chat = paths["/chats"]["get"]["responses"]["200"]["content"]["application/json"][number]

export default function ChatListScreen() {

    const { data, isRefetching, refetch } = $api.useQuery("get", "/chats")
    const { mutateAsync: createChat, isPending } = $api.useMutation("post", "/chats")
    const { logout } = useAuthStore()
    const { isDark, toggle } = useThemeStore()
    const logoutUser = useCallback(() => {
        router.dismissTo('/auth/login');
        logout();
    }, [])

    const goToNewChat = useCallback(async () => {
        try {
            const res = await createChat({
                body: {
                    title: "New chat"
                }
            })
            if (res.id) {
                router.push(`/chat/${res.id}`)
            }
        } catch (_e) {
            Alert.alert(`Error during initiate chat ${_e}`)
        }

    }, [])
    const renderChatItem = useCallback(({ item }: { item: Chat }) => <ChatItem
        id={item.id!.toLocaleString()}
        title={item.title ?? 'No title'}
        lastMessage={item.messages?.at(0)?.['content'] ?? item.created_at ?? ''}
        timestamp={item.messages?.at(0)?.['created_at'] ?? ''}
    />, []);

    return (
        <SafeAreaView className="flex-1 bg-white dark:bg-slate-900">
            <View className="flex-row items-center justify-between p-4 border-b dark:border-gray-800 border-gray-200">
                <Text className="text-2xl font-bold">Chats</Text>
                <View className='flex flex-row gap-2'>
                    <TouchableOpacity
                        onPress={logoutUser}
                        className="p-2"
                    >
                        <Ionicons name="log-out-outline" size={24} color="#3b82f6" />
                    </TouchableOpacity>
                    <ToggleThemeButton />
                </View>
            </View>
            <FlatList
                data={data ?? []}
                renderItem={renderChatItem}
                keyExtractor={(d) => `chat-${d.id}`}
                className="flex-1"
                refreshing={isRefetching}
                onRefresh={refetch}
                ListEmptyComponent={() =>
                    <View className='h-full w-full justify-center items-center'>
                        <Text>No chat</Text>
                    </View>}
            />
            <TouchableOpacity
                onPress={goToNewChat}
                disabled={isPending}
                className="absolute bottom-6 right-6 w-14 h-14 rounded-full bg-blue-500 items-center justify-center shadow-lg"
            >
                <Ionicons name="add" size={24} color="white" />
            </TouchableOpacity>
        </SafeAreaView>
    );
}