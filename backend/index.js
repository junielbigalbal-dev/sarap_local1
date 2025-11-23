
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const app = express();
app.use(cors());
app.use(express.json());

const PORT = process.env.PORT || 4000;

// Simple in-memory store for demo
const db = {
  users: [],
  vendors: [],
  posts: []
};

// Routes (simple stubs)
app.post('/api/auth/signup', (req,res)=>{
  // req.body: {name,email,password,role}
  // create user, send verification email (nodemailer) with token link
  res.json({ok:true, message:"Signup received - send verification email in real implementation."});
});

app.post('/api/auth/login',(req,res)=>{
  // authenticate and return JWT cookie or token
  res.json({ok:true, token:"DEMO_TOKEN"});
});

app.get('/api/vendor/posts', (req,res)=>{
  res.json({posts: db.posts});
});

// Admin protected route example
app.get('/api/admin/secret', (req,res)=>{
  // Implement server-side admin auth / ACL
  res.json({ok:true, secret:"this-route-requires-admin"});
});

app.listen(PORT, ()=>console.log('Server running on', PORT));
