import { Route, Routes } from "react-router";
import DashboardRoutes from "./Dashboard.routes";
import AuthRoutes from "./Auth.routes";

function AppRoutes() {
    return (
        <Routes>
            <Route path="/dashboard/*" element={<DashboardRoutes/>}/>
            <Route path="/*" element={<AuthRoutes/>}/>
        </Routes>
    )
}

export default AppRoutes;