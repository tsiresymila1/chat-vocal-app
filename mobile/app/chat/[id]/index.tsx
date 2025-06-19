import { Input } from '@/components/ui/input';
import { Text } from '@/components/ui/text';
import { $api } from '@/lib/api/client';
import { useColorScheme } from '@/lib/useColorScheme';
import { useAuthStore } from '@/store/auth';
import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import React, { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { Alert, FlatList, KeyboardAvoidingView, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

type Message = {
    id: string;
    text: string;
    isUser: boolean;
    audioUri?: string;
}

export default function ChatDetailScreen() {
    const { id } = useLocalSearchParams();
    const { isDarkColorScheme } = useColorScheme()
    const [messages, setMessages] = useState<Message[]>([]);
    const [inputText, setInputText] = useState('');
    const flatListRef = useRef<FlatList>(null);
    const { user } = useAuthStore()
    const { data: chatsMessages, isLoading, isFetching, isRefetching, refetch } = $api.useQuery("get", "/chats/{chat}/messages", {
        params: {
            path: {
                chat: parseInt(id.toString())
            }
        }
    })
    const { mutateAsync: sendMessage, isPending } = $api.useMutation("post", "/chats/{chat}/messages")

    const handleSendMessage = async (text: string) => {
        if (!text.trim()) return;
        const newMessage: Message = {
            id: Date.now().toString(),
            text,
            isUser: true,
        };
        setMessages(prev => [...prev, newMessage]);
        setInputText('');
        try {
            const res = await sendMessage({
                params: {
                    path: {
                        chat: parseInt(id.toString())
                    }
                },
                body: {
                    content: text,
                    type: 'text'
                }
            })
            if (res.ai_response) {
                setMessages(prev => [...prev, {
                    "id": `${res.ai_response?.id}`,
                    "isUser": res.ai_response?.user_id === user?.['id'],
                    "text": res.ai_response?.content ?? '',
                }]);
            }
        }
        catch (_) {
            Alert.alert("Error when sending message")
        }
    };

    useEffect(() => {
        if (chatsMessages && chatsMessages.data) {
            setMessages(chatsMessages.data.reverse().map(e => ({
                id: e.id!.toString(),
                isUser: e.user_id == user?.['id'],
                text: e.content!,
                audioUri: e.audio_path!,
            })))
        }
    }, [chatsMessages])

    useLayoutEffect(() => {
        if (refetch) {
            refetch();
        }
    }, []);

    return (
        <KeyboardAvoidingView className='w-full h-full'>
            <SafeAreaView className="flex-1 bg-white dark:bg-slate-900">
                <View className="flex-row items-center p-4 border-b border-gray-200 dark:border-gray-800">
                    <TouchableOpacity
                        onPress={() => router.back()}
                        className="mr-4"
                    >
                        <Ionicons name="arrow-back" color={isDarkColorScheme ? "white" : "black"} size={24} />
                    </TouchableOpacity>
                    <Text className="text-xl font-semibold">Chat {id}</Text>
                </View>
                <FlatList
                    ref={flatListRef}
                    data={messages}
                    keyExtractor={(item) => item.id}
                    renderItem={({ item: message }) => (
                        <View
                            className={`my-2 p-3 rounded-lg max-w-[80%] dark:bg-slate-900 bg-slate-100 ${message.isUser ? ' self-end' : 'self-start'}`}
                        >
                            <Text className={'dark:text-white text-slate-800'}>
                                {message.text}
                            </Text>
                        </View>
                    )}
                    contentContainerStyle={{ flexGrow: 1, padding: 16 }}
                    showsVerticalScrollIndicator={false}
                    refreshing={isRefetching}
                    onRefresh={refetch}
                    onContentSizeChange={() => {
                        setTimeout(() => {
                            if (flatListRef.current && messages.length > 0) {
                                flatListRef.current.scrollToOffset({ offset: 999999, animated: true });
                            }
                        }, 50);
                    }}
                    ListEmptyComponent={() => <View className='flex justify-center items-center'><Text>{isLoading || isFetching ? "Loading" : "No data"}</Text> </View>}
                />
                <View className="p-4 border-t border-gray-200 dark:border-gray-800 w-full">
                    <View className="flex flex-row items-center">
                        <View className='flex-1'>
                            <Input
                                value={inputText}
                                onChangeText={setInputText}
                                placeholder="Type a message..."
                                className=" mr-2 px-4 py-2 rounded-full border dark:border-gray-800 border-gray-300"
                            />
                        </View>
                        <TouchableOpacity
                            disabled={isPending}
                            onPress={() => handleSendMessage(inputText)}
                            className="w-10 h-10 rounded-full bg-blue-500 items-center justify-center"
                        >
                            <Ionicons name="send" size={20} color="white" />
                        </TouchableOpacity>
                        <TouchableOpacity
                            onPress={() => router.push(`/chat/${id}/audio`)}
                            className="ml-2 w-10 h-10 rounded-full bg-blue-500 items-center justify-center"
                        >
                            <Ionicons
                                name="mic"
                                size={20}
                                color="white"
                            />
                        </TouchableOpacity>
                    </View>
                </View>
            </SafeAreaView>
        </KeyboardAvoidingView>
    );
}