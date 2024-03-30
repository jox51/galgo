import React, { useEffect } from "react";
import { Link, Head } from "@inertiajs/react";
import AppLayer from "@/Components/AppLayer";

const NBARankings = () => {
    return (
        <>
            <Head title="G-NBA" />

            <AppLayer selectedSport={"NBA"} />
        </>
    );
};

export default NBARankings;
