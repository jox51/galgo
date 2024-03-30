import { Head } from "@inertiajs/react";
import AppLayer from "@/Components/AppLayer";

const Tennis = () => {
    return (
        <>
            <Head title="G-Tennis" />

            <AppLayer selectedSport={"Tennis"} />
        </>
    );
};

export default Tennis;
