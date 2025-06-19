
import { useAuthStore } from "@/store/auth";

import createFetchClient, { Middleware } from "openapi-fetch";
import createClient from "openapi-react-query";
import { paths } from "./openapi";

const fetchClient = createFetchClient<paths>({
    baseUrl: process.env.EXPO_PUBLIC_API_BASE_URL,
    fetch: async (input: Request) => {
        const res = await fetch(input);
        return res;
    },
    querySerializer: (query) => {
        const serializedParams: string[] = [];
        Object.entries(query).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                value.forEach((v) => {
                    serializedParams.push(
                        `${encodeURIComponent(key)}[]=${encodeURIComponent(v)}`
                    );
                });
            } else {
                serializedParams.push(
                    `${encodeURIComponent(key)}=${encodeURIComponent(value as string)}`
                );
            }
        });
        return serializedParams.join("&");
    },
});

const injectToken: Middleware = {
    async onRequest({ request }) {
        const token = useAuthStore.getState().token;
        request.headers.set("X-CSRF-TOKEN", "");
        if (token) {
            request.headers.set("Authorization", `Bearer ${token}`);
        }
    },
    onError({ error }) {
        console.error(error);
    },
};

fetchClient.use(injectToken);

export const $api = createClient<paths>(fetchClient);
