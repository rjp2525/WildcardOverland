import { AxiosInstance } from "axios";
import ziggyRoute, {
    Config as ZiggyConfig,
} from "../../vendor/tightenco/ziggy";
import { PageProps as AppPageProps } from "./PageProps";
import "vite/client";

declare global {
    interface Window {
        axios: AxiosInstance;
    }

    var route: typeof ziggyRoute;
    var Ziggy: ZiggyConfig;

    type Nullable<T> = T | null;
}

declare module "vue" {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute;
        can: (value: string) => boolean;
        is: (value: string) => boolean;
        $t: (value: string, attributes?: object) => string;
    }
}

declare module "@inertiajs/core" {
    // Augmenting with AppPageProps only. Extending InertiaPageProps here as
    // well would be self-referential, which silently defeats the merge.
    interface PageProps extends AppPageProps {}
}
