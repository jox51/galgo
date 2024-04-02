import React from "react";

const Button = ({ children, onClick }) => {
    return (
        <button
            type="button"
            className="rounded-md bg-green-600 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
            onClick={onClick}
        >
            {children}
        </button>
    );
};

export default Button;
