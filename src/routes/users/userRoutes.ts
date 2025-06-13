import { Router } from "express";
import { registerUserValidator, loginUserValidator } from "../../validators/userValidator";
import { createUsers, loginUsers } from "./controllers/userController";

const router = Router();

router.post("/register", registerUserValidator, createUsers);
router.post("/login", loginUserValidator, loginUsers);

export default router;
