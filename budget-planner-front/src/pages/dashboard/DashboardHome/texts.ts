import { CATEGORIES } from "../../../texts";

export const DASHBOARDHOME = {
    Topbar: {
        title: 'Bonjour, Loriana',
        description: 'Voici la répartition de votre foyer pour septembre 2026',
    },

    BudgetCards: [
        {
            ...CATEGORIES.essentials,
            description: "Loyer, factures, courses",
            valuePercent: "50%",
            value: 1180,
            totalValue: 1400,
        },
        {
            ...CATEGORIES.hobbies,
            description: "Sorties, abonnements, shopping",
            valuePercent: "30%",
            value: 690,
            totalValue: 840,
        },
        {
            ...CATEGORIES.saving,
            description: "Virement automatique livret A",
            valuePercent: "20%",
            value: 560,
            totalValue: 560,
        },
    ],

    MonthAllocation: {
        title: "Répartition du mois",
        householdIncome: "Revenus du foyer :",
        typeCard: "graph",
    },

    RecentTransactions: {
        title: "Transactions récentes",
        typeCard: "list-graph",
    },

    BreakdownByAccount: {
        title: "Répartition par compte",
        total: "Total :",
    },

    MonthlyEvolution: {
        title: "Évolution mensuelle",
    },

}