import { Router } from "express";
import { registerUserValidator } from "../../validators/userValidator";
import { createUsers } from "./controllers/userController";

const router = Router();

router.post("/register", registerUserValidator, createUsers);

export default router;
