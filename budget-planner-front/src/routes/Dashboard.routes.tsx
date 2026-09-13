import { Route, Routes } from "react-router";
import { DashboardMain } from "../components/layout/DashboardMain";
import { DashboardAccounts, DashboardHome } from "../pages/dashboard";

function DashboardRoutes() {
    return (
        <Routes>
            <Route element={<DashboardMain/>}>
                <Route index element={<DashboardHome/>}/>
                <Route path="/accounts" element={<DashboardAccounts/>}/>
            </Route>
        </Routes>
    )
}

export default DashboardRoutes;