// layouts/AppLayout.jsx
import React from "react";
import Navbar from "@/Components/Navbar";
import Footer from "@/Components/Footer";

export default function AppLayout({ auth, children }) {
    return (
        <>
            <Navbar auth={auth} />
            <main>{children}</main>
            <Footer />
        </>
    );
}
