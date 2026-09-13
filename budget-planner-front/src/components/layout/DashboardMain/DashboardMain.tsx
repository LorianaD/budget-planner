import { Outlet } from "react-router";
import { Main } from "../Main";
import { Sidebar } from "../Sidebar";

function DashboardMain() {
    return (
        <Main variant={'dashboard'}>
            <Sidebar/>
            <Outlet/> 
        </Main>
    )
}

export default DashboardMain;