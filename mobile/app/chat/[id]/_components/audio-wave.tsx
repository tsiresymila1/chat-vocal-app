import { FC, useEffect, useRef } from "react"
import { Animated } from "react-native"


type AudioWaveProps = {
    audioLevel: number,
    size: number,
    color: string,
    minScale: number
    maxScale: number
}

const AudioWave: FC<AudioWaveProps> = ({ audioLevel, size, color, minScale, maxScale }) => {
    const scaleAnim = useRef(new Animated.Value(minScale)).current;
    useEffect(() => {
        const  scale = audioLevel > 70 ? minScale : 1 + (audioLevel / 70);
        Animated.spring(scaleAnim, {
            toValue: scale,
            useNativeDriver: true,
            speed: 12,
            bounciness: 8,
        }).start();
    }, [audioLevel, minScale, maxScale]);
    return (
        <Animated.View
            style={{
                width: size,
                height: size,
                borderRadius: size / 2,
                backgroundColor: color,
                transform: [{ scale: scaleAnim }],
                alignSelf: 'center',
            }}
        />
    );
}
export default AudioWave