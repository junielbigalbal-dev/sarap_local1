
import React from "react";
import { Link } from "react-router-dom";

export default function Landing(){
  return (
    <div className="container">
      <header className="header">
        <img src="/assets/logo.png" alt="Sarap Local" className="logo-img" />
        <div>
          <div className="title">Sarap Local</div>
          <div className="sub">Sarap ng Lokal — Foodie finds near you (Biliran, PH)</div>
        </div>
        <div style={{marginLeft:"auto"}}>
          <Link to="/login"><button className="btn">Login</button></Link>
          <Link to="/signup"><button className="btn outline" style={{marginLeft:8}}>Sign Up</button></Link>
        </div>
      </header>

      <main className="hero">
        <div>
          <div className="headline">Discover and order local favorites</div>
          <div className="sub">Browse vendor posts, reels and order with "Order Now". Map shows vendors in Biliran province.</div>
          <div className="cta-row" style={{marginTop:12}}>
            <Link to="/customer"><button className="btn">Explore as Customer</button></Link>
            <Link to="/vendor"><button className="btn outline">Vendor Dashboard</button></Link>
          </div>
        </div>
        <div style={{minWidth:240}}>
          <div className="feed">
            <h4>News feed</h4>
            <div className="feed-item">
              <img className="product-img" src="/assets/logo.png" alt="post" />
              <div>
                <div style={{fontWeight:700}}>Sample Vendor</div>
                <div className="sub">New product launching today — order now!</div>
                <div style={{marginTop:8}}><button className="btn">Order Now</button></div>
              </div>
            </div>
            <p style={{fontSize:12,color:"#999",marginTop:8}}>This is a demo feed — vendors can post products and reels here.</p>
          </div>
        </div>
      </main>

      <section className="grid">
        <div className="feed">
          <h4>Latest reels</h4>
          <div style={{display:"flex",gap:8,overflowX:"auto",paddingTop:8}}>
            <div style={{minWidth:160,borderRadius:8,overflow:"hidden",background:"#eee",padding:8}}>Reel 1</div>
            <div style={{minWidth:160,borderRadius:8,overflow:"hidden",background:"#eee",padding:8}}>Reel 2</div>
            <div style={{minWidth:160,borderRadius:8,overflow:"hidden",background:"#eee",padding:8}}>Reel 3</div>
          </div>
        </div>

        <aside className="reels">
          <h4>Search & Map</h4>
          <div style={{marginTop:8}}>
            <input placeholder="Search vendor or product" style={{width:"100%",padding:10,borderRadius:8,border:"1px solid #eee"}} />
            <p style={{fontSize:13,color:"#666",marginTop:8}}>Map placeholder — integrate Google Maps or other provider and restrict to Biliran province.</p>
          </div>
        </aside>
      </section>
    </div>
  )
}
