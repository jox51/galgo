import React from "react";
import { MaterialReactTable } from "material-react-table";
import { parseISO, format } from "date-fns";
import { usePage } from "@inertiajs/react";
import { useThemeStore } from "@/store/themeStore"; // Adjust the import path as necessary
import { useTheme } from "@mui/material";

export default function HandballTable({ games }) {
    const { handballGames } = usePage().props;

    const { darkMode } = useThemeStore();
    const theme = useTheme();

    const darkBackgroundColor = "#212020"; // Example of a darker gray for dark mode

    // Adjust the mrtTheme based on the darkMode state
    const mrtTheme = {
        baseBackgroundColor: darkMode
            ? darkBackgroundColor
            : theme.palette.background.paper,
    };

    // const tableData = handballGames;

    const tableData = handballGames.map((game) => {
        // Decode fixture_data from JSON string to object
        const leagueData = JSON.parse(game.league);

        // const seasonData = JSON.parse(seasonDataRaw);
        const teamsData = JSON.parse(game.teams);

        return {
            ...game, // Spread other game properties if needed
            leagueData,
            teamsData,
        };
    });

    console.log({ tableData });
    const algoRankColor = (rank) => {
        const colors = {
            A: "#4CAF50", // Green
            B: "#8BC34A",
            C: "#CDDC39",
            D: "#FFEB3B",
            E: "#FFC107",
            F: "#FF9800",
            G: "#FF5722",
            H: "#F44336", // Red
        };

        return colors[rank.toUpperCase()] || "#9E9E9E"; // Default to grey if undefined
    };

    const columns = React.useMemo(
        () => [
            {
                accessorFn: (row) => `${row.game_date}  `,
                id: "game_date",
                header: "Date",
            },
            {
                accessorFn: (row) =>
                    `${row.teamsData.away.name} vs ${row.teamsData.home.name}`,
                id: "game",
                header: "Game",
            },
            {
                accessorFn: (row) => `${row.leagueData.name}`,
                id: "league",
                header: "League",
            },
            {
                accessorFn: (row) =>
                    row.home_probability > row.away_probability
                        ? `${row.teamsData.home.name}`
                        : `${row.teamsData.away.name}`,
                id: "toWin",
                header: "To Win",
            },
            {
                accessorKey: "algo_rank",
                header: "Algo Rank",
                Cell: ({ cell }) => {
                    const rank = cell.getValue();
                    return (
                        <div
                            style={{
                                display: "inline-block",
                                backgroundColor: algoRankColor(rank),
                                color: "#fff",
                                padding: "4px 12px", // Increased horizontal padding
                                minWidth: "36px", // Minimum width for the div
                                borderRadius: "18px", // Increase for more rounded edges if desired
                                textTransform: "uppercase", // Capitalize the letter
                                fontWeight: "bold",
                                textAlign: "center", // Ensure the content is centered, especially useful if you use minWidth
                            }}
                        >
                            {rank}
                        </div>
                    );
                },
            },
        ],
        []
    );

    return (
        <MaterialReactTable
            columns={columns}
            data={tableData}
            muiTableContainerProps={{
                sx: {
                    maxHeight: "calc(100vh - 64px)", // adjust based on your layout
                },
            }}
            enableRowSelection
            enableColumnOrdering
            enableGlobalFilter={false}
            mrtTheme={{
                baseBackgroundColor: mrtTheme.baseBackgroundColor,
                draggingBorderColor: theme.palette.secondary.main,
            }}
        />
    );
}
