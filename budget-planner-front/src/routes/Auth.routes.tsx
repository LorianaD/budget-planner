import { Route, Routes } from "react-router";
import { LoginPage, RegisterPage } from "../pages/auth";

function AuthRoutes() {
    return (
        <Routes>
            <Route path="/" element={<LoginPage/>}/>
            <Route path="/register" element={<RegisterPage/>}/>
        </Routes>
    )
}

export default AuthRoutes;
