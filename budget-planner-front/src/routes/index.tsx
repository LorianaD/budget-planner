import { Route, Routes } from "react-router";
import DashboardRoutes from "./Dashboard.routes";

function AppRoutes() {
    return (
        <Routes>
            <Route path="/dashboard/*" element={<DashboardRoutes/>}/>
        </Routes>
    )
}

export default AppRoutes;