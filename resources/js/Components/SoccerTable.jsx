import React, { useState, useEffect } from "react";
import { MaterialReactTable } from "material-react-table";
import { parseISO, format } from "date-fns";
import { usePage } from "@inertiajs/react";
import { useThemeStore } from "@/store/themeStore"; // Adjust the import path as necessary
import { useTheme } from "@mui/material";
import Button from "./Button";

export default function SoccerTable() {
    const { soccerGames } = usePage().props;

    const [filterMainLeagues, setFilterMainLeagues] = useState(false);
    const [filteredData, setFilteredData] = useState([]);

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
    // const tableData = soccerGames.map((game) => {
    //     // Decode fixture_data from JSON string to object
    //     const fixtureData = JSON.parse(game.fixture_data);

    //     // Extract and format date from fixtureData
    //     const gameDate = format(
    //         parseISO(fixtureData.fixture.date),
    //         "MM/dd/yyyy"
    //     );

    //     // Return a new object representing the table row
    //     return {
    //         ...game, // Spread other game properties if needed
    //         game_date: gameDate, // Include the formatted game date
    //         fixtureData, // Include the parsed fixtureData for further use if needed
    //     };
    // });

    useEffect(() => {
        // Filter data based on isLeagueMain value
        const filtered = filterMainLeagues
            ? soccerGames.filter(
                  (game) => JSON.parse(game.fixture_data).isLeagueMain
              )
            : soccerGames;

        // Map through the filtered or full data to prepare table data
        const tableData = filtered.map((game) => {
            const fixtureData = JSON.parse(game.fixture_data);
            const gameDate = format(
                parseISO(fixtureData.fixture.date),
                "MM/dd/yyyy"
            );

            return {
                ...game,
                game_date: gameDate,
                fixtureData,
            };
        });

        setFilteredData(tableData);
    }, [soccerGames, filterMainLeagues]);

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

    const toggleFilterMainLeagues = () => {
        setFilterMainLeagues(!filterMainLeagues);
    };

    return (
        <>
            <div className="flex justify-center pb-4">
                <Button variant="contained" onClick={toggleFilterMainLeagues}>
                    {filterMainLeagues
                        ? "Show All Leagues"
                        : "Filter Main Leagues"}
                </Button>
            </div>

            <MaterialReactTable
                columns={columns}
                data={filteredData}
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
        </>
    );
}
