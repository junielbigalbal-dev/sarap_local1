
import React, {useState} from "react";

/*
  Signup includes role selection (vendor | customer).
  After signup, the server should send email verification before enabling account.
*/
export default function Signup(){
  const [role,setRole] = useState("customer");
  return (
    <div className="container" style={{paddingTop:40}}>
      <h2>Sign Up</h2>
      <form onSubmit={(e)=>{e.preventDefault(); alert('Signup submitted. Server should send verification email.')}}>
        <div style={{marginTop:12}}>
          <input placeholder="Full name" required style={{width:"100%",padding:10,borderRadius:8}} />
        </div>
        <div style={{marginTop:12}}>
          <input placeholder="Email" type="email" required style={{width:"100%",padding:10,borderRadius:8}} />
        </div>
        <div style={{marginTop:12}}>
          <input placeholder="Password" type="password" required style={{width:"100%",padding:10,borderRadius:8}} />
        </div>

        <div style={{marginTop:12}}>
          <label style={{marginRight:8}}><input type="radio" checked={role==='customer'} onChange={()=>setRole('customer')} /> Customer</label>
          <label style={{marginLeft:8}}><input type="radio" checked={role==='vendor'} onChange={()=>setRole('vendor')} /> Vendor</label>
        </div>

        <div style={{marginTop:12}}>
          <button className="btn" type="submit">Create account</button>
        </div>

        <p style={{fontSize:13,color:"#666",marginTop:8}}>After signup, user must verify email. Implement verification link handling on the server to activate account.</p>
      </form>
    </div>
  )
}
