import { create } from "zustand";

export const useNBAStore = create((set) => ({
    nbaData: [],
    addNBAData: (nbaData) => set(() => ({ nbaData: nbaData })),
    // removeAllBears: () => set({ bears: 0 }),
    // updateBears: (newBears) => set({ bears: newBears }),
}));
