import AppLayer from "@/Components/AppLayer";
import { Head } from "@inertiajs/react";

export default function Picks({ games }) {
    return (
        <div>
            <Head title="Picks" />
            <AppLayer games={games} />
        </div>
    );
}
