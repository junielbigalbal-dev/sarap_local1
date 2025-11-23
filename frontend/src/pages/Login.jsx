
import React from "react";
import { useNavigate } from "react-router-dom";

/*
  Login page includes:
  - email / password
  - basic client-side validation
  - note: implement server-side auth + rate limiting + secure cookies / JWT
*/
export default function Login(){
  const nav = useNavigate();
  return (
    <div className="container" style={{paddingTop:40}}>
      <h2>Login</h2>
      <form onSubmit={(e)=>{e.preventDefault(); alert('This is a scaffold. Implement auth with backend.'); nav('/customer')}}>
        <div style={{marginTop:12}}>
          <input placeholder="Email" type="email" required style={{width:"100%",padding:10,borderRadius:8}} />
        </div>
        <div style={{marginTop:12}}>
          <input placeholder="Password" type="password" required style={{width:"100%",padding:10,borderRadius:8}} />
        </div>
        <div style={{marginTop:12,display:"flex",gap:8}}>
          <button className="btn" type="submit">Login</button>
        </div>
        <p style={{marginTop:8,fontSize:13,color:"#666"}}>Security notes: use HTTPS, secure HTTP-only cookies or stateless JWT, server-side rate limiting, account lockout for repeated failures.</p>
      </form>
    </div>
  )
}
