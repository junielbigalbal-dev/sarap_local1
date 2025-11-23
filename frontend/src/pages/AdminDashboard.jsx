
import React from "react";

/*
 Admin dashboard:
 - Must be protected by server-side ACLs and admin-only secrets.
 - Scaffold includes user management, vendor approvals, content moderation.
*/
export default function AdminDashboard(){
  return (
    <div className="container" style={{paddingTop:20}}>
      <h2>Admin (Protected)</h2>
      <div className="feed">
        <h4>Admin Tools</h4>
        <p style={{color:"#666"}}>User management, vendor approval, reports, and system settings go here.</p>
      </div>
    </div>
  )
}
