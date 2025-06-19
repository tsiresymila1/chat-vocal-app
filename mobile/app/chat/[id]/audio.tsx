import { Text } from '@/components/ui/text';
import { $api } from '@/lib/api/client';
import {
  SILENCE_DURATION,
  SILENCE_THRESHOLD,
} from '@/lib/audio';
import * as Speech from "expo-speech";
import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import AudioWave from './_components/audio-wave';
import { useAudio } from './_hooks/use-audio';
import { useAuthStore } from '@/store/auth';
import { useColorScheme } from '@/lib/useColorScheme';

export default function ChatAudioScreen() {
  const {
    isRecording,
    recordingRef,
    setupAudio,
    requestRecordingPermissions,
    startRecording: startAudioRecording,
    stopRecording: stopAudioRecording
  } = useAudio();

  const { id } = useLocalSearchParams();
  const [isProcessing, setIsProcessing] = useState(false);
  const [currentLevel, setCurrentLevel] = useState(0);
  const silenceTimerRef = useRef<number | null>(null);
  const speakingIntervalRef = useRef<number | null>(null);
  const { isDarkColorScheme } = useColorScheme()
  const [isSpeaking, setIsSpeaking] = useState(false);
  const { token } = useAuthStore();
  const { mutateAsync: transcribeAudio } = $api.useMutation("post", "/chats/{chat}/messages/transcribe");

  const resetSilenceTimer = () => {
    if (silenceTimerRef.current) {
      clearTimeout(silenceTimerRef.current);
    }
    silenceTimerRef.current = null;
  };

  const handleMeteringUpdate = useCallback((level: number) => {
    if (level < SILENCE_THRESHOLD) {
      if (!silenceTimerRef.current) {
        silenceTimerRef.current = setTimeout(() => {
          handleStopRecording();
        }, SILENCE_DURATION);
      }
    } else {
      resetSilenceTimer();
    }
    setCurrentLevel(Math.abs(level));
  }, [silenceTimerRef.current, recordingRef.current, isRecording]);

  const handleStartRecording = async () => {
    try {
      const hasPermission = await requestRecordingPermissions();
      if (!hasPermission) {
        console.error('Permission to access microphone was denied');
        return;
      }
      await setupAudio();
      await startAudioRecording(handleMeteringUpdate);
      setCurrentLevel(0);
    } catch (err) {
      console.error('Failed to start recording', err);
    }
  };

  const handleStopRecording = useCallback(async () => {
    if (!recordingRef.current) return;
    setIsProcessing(true);
    try {
      const uri = await stopAudioRecording();
      if (uri) {
        const transRes = await transcribeAudio({
          params: { path: { chat: parseInt(id.toString()) } },
          body: { audio: {} },
          bodySerializer: () => {
            const formData = new FormData();
            formData.append("audio", {
              uri,
              type: "audio/m4a",
              name: "audio.m4a"
            } as unknown as Blob);
            return formData;
          },
          headers: { 'Accept': 'application/json' }
        });

        const res = await fetch(`${process.env.EXPO_PUBLIC_API_BASE_URL}/chats/${id}/messages`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            'Authorization': `Bearer ${token}`,
            'Accept': `application/json`
          },
          body: JSON.stringify({
            content: transRes.transcription ?? '',
            type: 'audio',
            audio_path: transRes.audio_path,
            stream: false
          })
        });

        const data = await res.json();
        const ai_response_content = data['ai_response']['content'];

        Speech.speak(ai_response_content, {
          language: transRes?.language ?? 'en',
          onStart: () => {
            setIsSpeaking(true);
            speakingIntervalRef.current = setInterval(() => {
              setCurrentLevel(Math.random() * 100);
            }, 100);
          },
          onDone: () => {
            setIsSpeaking(false);
            if (speakingIntervalRef.current) {
              clearInterval(speakingIntervalRef.current);
              speakingIntervalRef.current = null;
            }
            setCurrentLevel(0);
          }
        });
      }
    } catch (err) {
      console.error(err);
      Alert.alert(`Error: ${err}`);
    } finally {
      setIsProcessing(false);
    }
  }, [recordingRef.current, id]);

  useEffect(() => {
    return () => {
      handleStopRecording();
      if (speakingIntervalRef.current) {
        clearInterval(speakingIntervalRef.current);
      }
    };
  }, []);

  return (
    <SafeAreaView className="flex-1 bg-white dark:bg-slate-900">
      <View className="flex-1 justify-between p-5">
        <View className="flex-row items-center">
          {!isRecording &&
            <TouchableOpacity onPress={() => router.back()} className="mr-4">
              <Ionicons name="close" size={24} color={isDarkColorScheme ? 'white' : 'black'} />
            </TouchableOpacity>}
          <Text className="text-xl font-semibold dark:text-white">
            Audio Chat {id}
          </Text>
        </View>

        <View className="flex-1 justify-center items-center">
          <AudioWave
            audioLevel={currentLevel}
            size={150}
            color="#3b82f6"
            minScale={1}
            maxScale={2}
          />
        </View>

        <View className="items-center mb-10">
          <TouchableOpacity
            disabled={isProcessing}
            onPress={isRecording ? handleStopRecording : handleStartRecording}
            className={`w-20 h-20 rounded-full items-center justify-center ${isRecording ? 'bg-red-500' : 'bg-blue-500'}`}
          >
            <Ionicons
              name={isRecording ? 'stop' : 'mic'}
              size={32}
              color="white"
            />
          </TouchableOpacity>
          <Text className="mt-4 text-gray-500 dark:text-gray-400">
            {isRecording ? 'Listening...' : isSpeaking ? 'Speaking ...' : isProcessing ? 'Thinking ...' : 'Tap to record'}
          </Text>
        </View>
      </View>
    </SafeAreaView>
  );
}
