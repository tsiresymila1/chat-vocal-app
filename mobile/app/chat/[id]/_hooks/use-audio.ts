

import { Audio } from 'expo-av';
import { useCallback, useRef, useState } from 'react';

export const useAudio = () => {

    const [isRecording, setIsRecording] = useState<boolean>(false)
    const [isGranted, setIsGranted] = useState<boolean>(false)
    const [recording, setRecording] = useState<Audio.Recording | null>(null)
    const recordingRef = useRef<Audio.Recording | null>(null)

    const setupAudio = useCallback(async () => {
        await Audio.setAudioModeAsync({
            allowsRecordingIOS: true,
            playsInSilentModeIOS: true,
        });
    }, [])

    const requestRecordingPermissions = useCallback(async () => {
        const { status } = await Audio.requestPermissionsAsync();
        const isGraned = status === 'granted'
        setIsGranted(isGraned)
        return isGraned;
    }, [])

    const startRecording = useCallback(async (onMeteringUpdate: (level: number) => void) => {
        const { recording } = await Audio.Recording.createAsync(
            Audio.RecordingOptionsPresets.HIGH_QUALITY,
            (status) => {
                if (status.isRecording) {
                    setIsRecording(status.isRecording)
                    onMeteringUpdate(status.metering || 0);
                }
                if (status.isDoneRecording) {
                    setIsRecording(false)
                }
            },
            100
        );
        recordingRef.current = recording
        setRecording(recording)
    }, [])

    const stopRecording = useCallback(async () => {
        await recordingRef.current?.stopAndUnloadAsync();
        const uri = recordingRef.current?.getURI();
        setIsRecording(false)
        recordingRef.current = null;
        setRecording(null)
        return uri;
    }, [recordingRef.current])

    return { setupAudio, requestRecordingPermissions, startRecording, stopRecording,recording,recordingRef, isGranted, isRecording }
}