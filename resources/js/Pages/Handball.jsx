import React from "react";
import AppLayer from "@/Components/AppLayer";
import { Head } from "@inertiajs/react";

const Handball = () => {
    return (
        <>
            <Head title="G-Handball" />

            <AppLayer selectedSport={"Handball"} />
        </>
    );
};

export default Handball;
