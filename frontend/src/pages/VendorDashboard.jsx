
import React from "react";

/*
 Vendor dashboard scaffold:
 - Manage products (list, add)
 - Notifications & messages
 - Analytics (sales this month stub)
 - Reels & posts
*/
export default function VendorDashboard(){
  return (
    <div className="container" style={{paddingTop:20}}>
      <h2>Vendor Dashboard</h2>
      <div style={{display:"flex",gap:12,flexWrap:"wrap"}}>
        <div style={{flex:1,minWidth:300}}>
          <div className="feed">
            <h4>Manage Products</h4>
            <p style={{color:"#666"}}>Product list and add/edit UI goes here.</p>
          </div>
          <div style={{height:12}}/>
          <div className="feed"><h4>Notifications</h4><p style={{color:"#666"}}>New orders will appear with counts on the button/icon.</p></div>
        </div>

        <aside style={{width:320}}>
          <div className="feed"><h4>Analytics</h4><p style={{color:"#666"}}>Sales this month: <strong>0</strong> (stub)</p></div>
          <div style={{height:12}}/>
          <div className="reels"><h4>Post Reel</h4><p style={{color:"#666"}}>Upload short videos to promote products.</p></div>
        </aside>
      </div>
    </div>
  )
}
