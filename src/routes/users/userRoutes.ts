import { Router } from "express";
import { createUsers } from "./controllers/userController";
const router = Router();

// ====== Routes ======

// ➕ POST: Add Users
router.post("/register", createUsers);
export default router;
