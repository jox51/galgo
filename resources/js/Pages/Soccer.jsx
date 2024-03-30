import React, { useEffect } from "react";
import { Link, Head } from "@inertiajs/react";
import AppLayer from "@/Components/AppLayer";

const Soccer = () => {
    return (
        <>
            <Head title="G-Soccer" />

            <AppLayer selectedSport={"Soccer"} />
        </>
    );
};

export default Soccer;
