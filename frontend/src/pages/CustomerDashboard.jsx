
import React from "react";

/*
  Customer dashboard scaffold:
  - News feed (vendor posts)
  - Reels (videos)
  - Cart
  - Map (Biliran)
  - Messages & notifications
  - Profile settings and 'See as customer' preview not needed here (this is customer view)
*/
export default function CustomerDashboard(){
  return (
    <div className="container" style={{paddingTop:20}}>
      <h2>Customer Dashboard</h2>
      <div style={{display:"flex",gap:12,flexWrap:"wrap"}}>
        <div style={{flex:1,minWidth:280}}>
          <div className="feed">
            <h4>News Feed</h4>
            <div className="feed-item">
              <img className="product-img" src="/assets/logo.png" alt="post" />
              <div>
                <div style={{fontWeight:700}}>Vendor sample</div>
                <div style={{marginTop:6}}>Try our new lumpia — tap Order Now</div>
                <div style={{marginTop:8}}><button className="btn">Order Now</button></div>
              </div>
            </div>
          </div>

          <div style={{height:12}}/>
          <div className="feed">
            <h4>Your Cart</h4>
            <p style={{color:"#666"}}>Cart items will appear here.</p>
          </div>
        </div>

        <aside style={{width:320}}>
          <div className="reels"><h4>Reels</h4><div style={{paddingTop:8}}>Video placeholders</div></div>
          <div style={{height:12}}/>
          <div className="feed"><h4>Map - Biliran</h4><p style={{fontSize:13,color:"#666"}}>Integrate map provider and restrict to Biliran province.</p></div>
        </aside>
      </div>
    </div>
  )
}
