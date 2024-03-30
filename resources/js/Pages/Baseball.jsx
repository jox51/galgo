import React from "react";
import { Head } from "@inertiajs/react";
import AppLayer from "@/Components/AppLayer";

const Baseball = () => {
    return (
        <>
            <Head title="G-Baseball" />

            <AppLayer selectedSport={"Baseball"} />
        </>
    );
};

export default Baseball;
