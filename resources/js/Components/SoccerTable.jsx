import React from "react";
import { MaterialReactTable } from "material-react-table";
import { parseISO, format } from "date-fns";
import { usePage } from "@inertiajs/react";
import { useThemeStore } from "@/store/themeStore"; // Adjust the import path as necessary
import { useTheme } from "@mui/material";
export default function SoccerTable() {
    const { soccerGames } = usePage().props;

    const { darkMode } = useThemeStore();
    const theme = useTheme();

    const darkBackgroundColor = "#212020"; // Example of a darker gray for dark mode

    // Adjust the mrtTheme based on the darkMode state
    const mrtTheme = {
        baseBackgroundColor: darkMode
            ? darkBackgroundColor
            : theme.palette.background.paper,
    };

    // Parse the fixture_data JSON string and prepare table data
    const tableData = soccerGames.map((game) => {
        // Decode fixture_data from JSON string to object
        const fixtureData = JSON.parse(game.fixture_data);

        // Extract and format date from fixtureData
        const gameDate = format(
            parseISO(fixtureData.fixture.date),
            "MM/dd/yyyy"
        );

        // Return a new object representing the table row
        return {
            ...game, // Spread other game properties if needed
            game_date: gameDate, // Include the formatted game date
            fixtureData, // Include the parsed fixtureData for further use if needed
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
                accessorKey: "game_date",
                header: "Date",
                Cell: ({ cell }) => cell.getValue(),
            },

            {
                accessorFn: (row) =>
                    `${row.fixtureData.teams.away.name} vs ${row.fixtureData.teams.home.name}`,
                id: "game",
                header: "Game",
            },
            {
                accessorFn: (row) => `${row.fixtureData.league.name}`,
                id: "league",
                header: "League",
            },
            {
                accessorFn: (row) =>
                    row.home_probability > row.away_probability
                        ? `${row.fixtureData.teams.home.name}`
                        : `${row.fixtureData.teams.away.name}`,
                id: "toWin",
                header: "To Win",
            },
            {
                accessorFn: (row) =>
                    `${row.fixtureData.away_goals} - ${row.fixtureData.home_goals}`,
                id: "correcScore",
                header: "Correct Score",
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
