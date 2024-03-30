import React from "react";
import { MaterialReactTable } from "material-react-table";
import { parseISO, format } from "date-fns";
import { usePage } from "@inertiajs/react";

export default function TennisTable() {
    const { tennisGames } = usePage().props;

    // Parse the fixture_data JSON string and prepare table data
    const tableData = tennisGames.map((game) => {
        // Decode fixture_data from JSON string to object
        const seasonDataRaw = JSON.parse(game.season);
        const seasonData = JSON.parse(seasonDataRaw);
        const homeTeamData = JSON.parse(game.homeTeam);
        const awayTeamData = JSON.parse(game.awayTeam);

        // Extract and format date from fixtureData
        // const gameDate = format(
        //     parseISO(fixtureData.fixture.date),
        //     "MM/dd/yyyy"
        // );

        // Return a new object representing the table row
        return {
            ...game, // Spread other game properties if needed
            seasonData,
            homeTeamData,
            awayTeamData,
        };
    });

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
                accessorKey: "matchStartDate",
                header: "Date",
                Cell: ({ cell }) => cell.getValue(),
            },

            {
                accessorFn: (row) =>
                    `${row.homeTeamData.name} vs ${row.awayTeamData.name}`,
                id: "game",
                header: "Game",
            },
            {
                accessorFn: (row) => `${row.seasonData.name}`,
                id: "season",
                header: "Season",
            },
            {
                accessorFn: (row) =>
                    row.homeTeamData.probability > row.awayTeamData.probability
                        ? `${row.homeTeamData.name}`
                        : `${row.awayTeamData.name}`,
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
        />
    );
}
